<?php
/**
 * Show admin notices in distinct types of screens
 *
 * @since       1.8.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Components
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

final class AtumAdminNotices {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumAdminNotices
	 */
	private static $instance;

	/**
	 * The registered notices
	 *
	 * @var array
	 */
	private static $notices = [];

	/**
	 * User meta key to control the current user dismissed notices
	 */
	const DISMISSED_NOTICES = 'atum_dismissed_notices';

	/**
	 * Transient key used for persistent notices
	 */
	const NOTICES_TRANSIENT_KEY = 'admin_notices';


	/**
	 * AtumAdminNotices constructor.
	 *
	 * @since 1.8.2
	 */
	private function __construct() {

		// Register the notices after the current screen has been set.
		add_action( 'current_screen', array( $this, 'register_notices' ) );

		// ATUM notice dismissals.
		add_action( 'wp_ajax_atum_dismiss_notice', array( $this, 'dismiss_notice_ajax' ) );

	}


	/**
	 * Register the notices to WP using hooks
	 *
	 * @since 1.8.2
	 *
	 * @param \WP_Screen $current_screen
	 */
	public function register_notices( $current_screen ) {

		$transient_key_notices = AtumCache::get_transient_key( self::NOTICES_TRANSIENT_KEY );
		$persistent_notices    = AtumCache::get_transient( $transient_key_notices, TRUE );

		if ( ! empty( $persistent_notices ) ) {
			self::$notices = array_merge( self::$notices, $persistent_notices );
		}

		if ( ! empty( self::$notices ) ) {

			$hook_name = 'admin_notices'; // Standard WP way.

			// Add notices to the edit post screens using a different hook.
			if (
				'post' === $current_screen->base && (
					'edit' === $current_screen->action || isset( $_GET['action'] ) && 'edit' === $_GET['action']
				)
			) {
				$hook_name = 'edit_form_top'; // Add the notices at the top of the post edit form.
			}

			// Add the notices depending on the current screen.
			add_action( $hook_name, function() use ( $transient_key_notices, $persistent_notices ) {

				$printed_notices = 0;

				foreach ( self::$notices as $notice ) :

					if ( ! empty( $notice['dismiss_key'] ) && self::is_notice_dismissed( $notice['dismiss_key'] ) ) :
						continue;
					endif;

					$printed_notices++;
					?>
					<div class="atum-notice notice notice-<?php echo esc_attr( $notice['type'] ) ?><?php if ( $notice['dismissible'] ) echo esc_attr( ' is-dismissible' ) ?>"
						data-dismiss-key="<?php echo esc_attr( $notice['dismiss_key'] ) ?>"
					>
						<p>
							<?php if ( ! isset( $notice['bold'] ) || TRUE === $notice['bold'] ) : ?>
							<strong>
							<?php endif; ?>
								<?php echo wp_kses_post( $notice['message'] ); ?>
							<?php if ( ! isset( $notice['bold'] ) || TRUE === $notice['bold'] ) : ?>
							</strong>
							<?php endif; ?>

							<?php if ( $notice['dismissible'] ) : ?>
								<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', ATUM_TEXT_DOMAIN ); ?></span></button>
							<?php endif; ?>
						</p>
					</div>
				<?php endforeach; ?>

				<?php if ( $printed_notices ) : ?>
					<script type="text/javascript">
						jQuery( function( $ ) {
							var $notices = $( '.atum-notice' );

							$notices.on( 'click', '.notice-dismiss', function() {
								var $notice = $( this ).closest( '.atum-notice' );

								$notice.fadeTo( 100, 0, function() {
									$notice.slideUp( 100, function() {
										$notice.remove();
									} );
								} );

								if ( $notice.data('dismiss-key') ) {
									$.ajax({
										url   : ajaxurl,
										method: 'POST',
										data  : {
											action  : 'atum_dismiss_notice',
											security: '<?php echo wp_create_nonce( 'dismiss-atum-notice' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
											key     : $notice.data('dismiss-key')
										}
									});
								}

							} );
						} );
					</script>
				<?php endif; ?>

				<?php
				// Clear the notices, so they aren't shown again.
				if ( ! empty( $persistent_notices ) ) {
					AtumCache::delete_transients( $transient_key_notices );
				}

				self::$notices = [];

			} );

		}

	}

	/**
	 * Add a new admin notice
	 *
	 * @since 1.8.2
	 *
	 * @param string $message        The message to be shown in the notice.
	 * @param string $type           One of: 'error', 'warning', 'success' or 'info'.
	 * @param bool   $is_dismissible Optional. Whether to add a button for closing the notice.
	 * @param bool   $persistent     Optional. In some cases (like a post edit screen), we need to save the notice on a transient for showing it later.
	 * @param string $dismiss_key    Optional. Only for dismissible notices that we don't want to show again after dismissed.
	 * @param bool   $bold           Optional. Whether to display the entire notice into a <strong></strong>.
	 */
	public static function add_notice( $message, $type, $is_dismissible = FALSE, $persistent = FALSE, $dismiss_key = '', $bold = TRUE ) {

		if ( $persistent ) {

			$transient_key_notices = AtumCache::get_transient_key( self::NOTICES_TRANSIENT_KEY );
			$persistent_notices    = AtumCache::get_transient( $transient_key_notices, TRUE );
			$persistent_notices    = is_array( $persistent_notices ) ? $persistent_notices : [];

			// Ensure that the same notices are not added more than once.
			if ( ! empty( $persistent_notices ) && ! empty( wp_list_filter( $persistent_notices, [ 'message' => $message ] ) ) ) {
				return;
			}

			$persistent_notices[] = array(
				'message'     => $message,
				'type'        => $type,
				'dismissible' => $is_dismissible,
				'dismiss_key' => $dismiss_key,
				'bold'        => $bold,
			);

			AtumCache::set_transient( $transient_key_notices, $persistent_notices, HOUR_IN_SECONDS, TRUE );

		}
		else {

			self::$notices[] = array(
				'message'     => $message,
				'type'        => $type,
				'dismissible' => $is_dismissible,
				'dismiss_key' => $dismiss_key,
				'bold'        => $bold,
			);

		}

	}

	/**
	 * Dismiss the ATUM notices
	 *
	 * @package Helpers
	 *
	 * @since 1.8.2
	 */
	public function dismiss_notice_ajax() {

		check_ajax_referer( 'dismiss-atum-notice', 'security' );

		if ( ! empty( $_POST['key'] ) ) {
			self::dismiss_notice( esc_attr( $_POST['key'] ) );
		}

		wp_die();
	}

	/**
	 * Add a notice to the list of dismissed notices for the current user
	 *
	 * @since 1.8.2
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

		return update_user_meta( $current_user_id, self::DISMISSED_NOTICES, $user_dismissed_notices );

	}

	/**
	 * Get the list of ATUM's dismissed notices for the current user
	 *
	 * @since 1.8.2
	 *
	 * @param int $user_id  The ID of the user to retrieve the dismissed notices from.
	 *
	 * @return array|bool
	 */
	public static function get_dismissed_notices( $user_id = NULL ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		return apply_filters( 'atum/admin_notices/dismissed_notices', get_user_meta( $user_id, self::DISMISSED_NOTICES, TRUE ) );
	}

	/**
	 * Check whether the specified notice was previously dismissed
	 *
	 * @since 1.8.2
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


	/*******************
	 * Instance methods
	 *******************/

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
	 * @return AtumAdminNotices instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
