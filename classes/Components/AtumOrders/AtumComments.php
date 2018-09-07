<?php
/**
 * Handle ATUM comments (ATUM Order Notes)
 *
 * @package         Atum/Components
 * @subpackage      AtumOrders
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\Components\AtumOrders;

defined( 'ABSPATH' ) || die;


class AtumComments {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumComments
	 */
	private static $instance;

	/**
	 * The key name used for ATUM Order comments
	 */
	const NOTES_KEY = ATUM_PREFIX . 'order_note';

	/**
	 * AtumComments constructor
	 *
	 * @since 1.2.4
	 */
	private function __construct() {

		// Exclude ATUM Order notes from queries.
		add_filter( 'comments_clauses', array( $this, 'exclude_atum_order_notes' ), 10, 1 );
		add_action( 'comment_feed_where', array( $this, 'exclude_atum_order_notes_from_feed_where' ) );

		// Recount comments. Priority must be higher than the \WC_Comments filter (10).
		add_filter( 'wp_count_comments', array( $this, 'wp_count_comments' ), 11, 2 );

	}

	/**
	 * Exclude ATUM Order notes from queries and RSS
	 *
	 * This code should exclude atum_order_note comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded
	 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and
	 * shop managers can view logs anyway.
	 *
	 * This must be filtered here because the AtumOrderModel class won't be loaded if any of the dependencies is not met,
	 * and we've to ensure that the ATUM Order notes are not displayed in the WP queries.
	 *
	 * @since 1.2.4
	 *
	 * @param  array $clauses
	 *
	 * @return array
	 */
	public function exclude_atum_order_notes( $clauses ) {
		// *** The 'log_note' is deprecated and could be deleted in future versions ***
		$clauses['where'] .= ( ! empty( $clauses['where'] ) ? ' AND ' : '' ) . " comment_type NOT IN ('" . self::NOTES_KEY . "', 'log_note') ";
		return $clauses;
	}

	/**
	 * Exclude ATUM Order notes from queries and RSS
	 *
	 * @since 1.2.4
	 *
	 * @param  string $where
	 *
	 * @return string
	 */
	public function exclude_atum_order_notes_from_feed_where( $where ) {
		// *** The 'log_note' is deprecated and could be deleted in future versions ***
		// This filter always has value and comes with the where statement.
		return "{$where} AND comment_type NOT IN ('" . self::NOTES_KEY . "', 'log_note') ";
	}

	/**
	 * Remove ATUM Order notes from wp_count_comments()
	 * This filter overrides the \WC_Comments filter adding to exclude list the ATUM Order notes
	 *
	 * @since  1.2.4
	 *
	 * @param object $stats   Comment stats.
	 * @param int    $post_id Post ID.
	 *
	 * @return object
	 */
	public function wp_count_comments( $stats, $post_id ) {

		global $wpdb;

		if ( 0 === $post_id ) {

			$stats = get_transient( ATUM_PREFIX . 'count_comments' );

			if ( ! $stats ) {
				$stats = array();

				// *** The 'log_note' is deprecated and could be deleted in future versions ***
				$count = $wpdb->get_results( "
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type NOT IN ('order_note', 'webhook_delivery', 'log_note', '" . self::NOTES_KEY . "')
					GROUP BY comment_approved
				", ARRAY_A ); // WPCS: unprepared SQL ok.

				$total    = 0;
				$approved = array(
					'0'            => 'moderated',
					'1'            => 'approved',
					'spam'         => 'spam',
					'trash'        => 'trash',
					'post-trashed' => 'post-trashed',
				);

				foreach ( (array) $count as $row ) {

					// Don't count post-trashed toward totals.
					if ( ! in_array( $row['comment_approved'], [ 'post-trashed', 'trash' ] ) ) {
						$total += $row['num_comments'];
					}

					if ( isset( $approved[ $row['comment_approved'] ] ) ) {
						$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
					}

				}

				$stats['total_comments'] = $total;
				$stats['all']            = $total;

				foreach ( $approved as $key ) {
					if ( empty( $stats[ $key ] ) ) {
						$stats[ $key ] = 0;
					}
				}

				$stats = (object) $stats;
				set_transient( ATUM_PREFIX . 'count_comments', $stats );
			}
		}

		return $stats;

	}

	/**
	 * Delete comments count cache whenever there is new comment or the status of a comment changes
	 * Cache will be regenerated next time the wp_count_comments() method is called
	 *
	 * @since 1.2.4
	 */
	public function delete_comments_count_cache() {
		delete_transient( ATUM_PREFIX . 'count_comments' );
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
	 * @return AtumComments instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
