<?php
/**
 * View for the Help Sidebar on Stock Central page
 *
 * @since 0.0.5
 */

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;

?>
<br/>
<p><a href="https://www.stockmanagementlabs.com" target="_blank"><?php esc_html_e( 'Visit our Website for More', ATUM_TEXT_DOMAIN ) ?></a></p>
<p><a href="https://forum.stockmanagementlabs.com/t/atum-documentation" target="_blank"><?php esc_html_e( 'Read the Docs', ATUM_TEXT_DOMAIN ) ?></a></p>
<p><a href="https://www.youtube.com/channel/UCcTNwTCU4X_UrIj_5TUkweA" target="_blank"><?php esc_html_e( 'Watch our Videos', ATUM_TEXT_DOMAIN ) ?></a></p>
<p><a href="https://forum.stockmanagementlabs.com/all" target="_blank"><?php esc_html_e( 'Get Support', ATUM_TEXT_DOMAIN ) ?></a></p>

<?php if ( Addons::has_valid_key() ) : ?>
<p><a href="https://stockmanagementlabs.ticksy.com/" target="_blank"><?php esc_html_e( 'Open Premium Support Ticket', ATUM_TEXT_DOMAIN ) ?></a></p>
<?php endif;
