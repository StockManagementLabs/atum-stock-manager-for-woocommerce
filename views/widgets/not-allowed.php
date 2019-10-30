<?php
/**
 * View for the "not allowed" message used in ATUM Dashboard when the user has no permission to view a widget
 *
 * @since 1.6.2
 */

defined( 'ABSPATH' ) || die;
?>

<div class="no-allowed-widget">

	<div class="alert alert-warning">
		<i class="atum-icon atmi-warning"></i>
		<?php esc_html_e( 'You are not allowed to view statistics', ATUM_TEXT_DOMAIN ); ?>
	</div>

</div>
