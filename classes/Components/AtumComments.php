<?php
/**
 * @package         Atum
 * @subpackage      Components
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * Handle ATUM comments (Log Notes)
 */

namespace Atum\Components;

defined( 'ABSPATH' ) or die;


class AtumComments {

	/**
	 * The singleton instance holder
	 * @var AtumComments
	 */
	private static $instance;

	/**
	 * AtumComments constructor
	 *
	 * @since 1.2.4
	 */
	private function __construct() {

		// Exclude log notes from queries
		add_filter( 'comments_clauses', array( $this, 'exclude_log_notes' ), 10, 1 );
		add_action( 'comment_feed_where', array( $this, 'exclude_log_notes_from_feed_where' ) );

		// Recount comments. Priority must be higher than the \WC_Comments filter (10)
		add_filter( 'wp_count_comments', array( $this, 'wp_count_comments' ), 11, 2 );

	}

	/**
	 * Exclude log notes from queries and RSS
	 *
	 * This code should exclude log_note comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded
	 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and
	 * shop managers can view logs anyway
	 *
	 * This must be filtered here because the InventoryLogs' class won't be loaded if any of the dependencies is not met,
	 * and we've to ensure that the log notes are not displayed in the WP queries
	 *
	 * @since 1.2.4
	 *
	 * @param  array $clauses
	 *
	 * @return array
	 */
	public function exclude_log_notes( $clauses ) {
		$clauses['where'] .= ( $clauses['where'] ? ' AND ' : '' ) . " comment_type != 'log_note' ";
		return $clauses;
	}

	/**
	 * Exclude log notes from queries and RSS
	 *
	 * @since 1.2.4
	 *
	 * @param  string $where
	 *
	 * @return string
	 */
	public function exclude_log_notes_from_feed_where( $where ) {
		return ( $where ? ' AND ' : '' ) . " comment_type != 'log_note' ";
	}

	/**
	 * Remove log notes from wp_count_comments()
	 * This filter overrides the \WC_Comments filter adding to exclude list the ATUM log notes
	 *
	 * @since  1.2.4
	 *
	 * @param  object $stats   Comment stats
	 * @param  int    $post_id Post ID
	 *
	 * @return object
	 */
	public function wp_count_comments( $stats, $post_id ) {

		global $wpdb;

		if ( 0 === $post_id ) {

			$stats = get_transient( ATUM_PREFIX . 'count_comments' );

			if ( ! $stats ) {
				$stats = array();

				$count = $wpdb->get_results( "
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type NOT IN ('order_note', 'webhook_delivery', 'log_note')
					GROUP BY comment_approved
				", ARRAY_A );

				$total = 0;
				$approved = array(
					'0'            => 'moderated',
					'1'            => 'approved',
					'spam'         => 'spam',
					'trash'        => 'trash',
					'post-trashed' => 'post-trashed',
				);

				foreach ( (array) $count as $row ) {
					// Don't count post-trashed toward totals.
					if ( 'post-trashed' !== $row['comment_approved'] && 'trash' !== $row['comment_approved'] ) {
						$total += $row['num_comments'];
					}
					if ( isset( $approved[ $row['comment_approved'] ] ) ) {
						$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
					}
				}

				$stats['total_comments'] = $total;
				$stats['all'] = $total;
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
	public function __clone() {
		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
	}

	public function __sleep() {
		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
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