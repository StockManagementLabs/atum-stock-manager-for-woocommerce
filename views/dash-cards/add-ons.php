<?php
/**
 * View for the add-ons dash card
 *
 * @since 1.9.27
 */

defined( 'ABSPATH' ) || die;

?>
<div class="dash-card add-ons">

	<div class="card-content">

		<div class="card-content__header">
			<h5 class="h5-secondary"><?php esc_html_e( 'Add-ons', ATUM_TEXT_DOMAIN ) ?></h5>
			<h2><?php esc_html_e( 'Endless Possibilities', ATUM_TEXT_DOMAIN ) ?></h2>
		</div>

		<p><?php esc_html_e( 'Expand your inventory control with our premium add-ons. No storage is left unattended, no item uncounted and no production line inefficient.', ATUM_TEXT_DOMAIN ) ?></p>

		<div class="card-content__buttons">
			<a href="https://stockmanagementlabs.com/addons/" class="btn btn-tertiary" target="_blank"><?php esc_html_e( 'View Add-ons', ATUM_TEXT_DOMAIN ) ?></a>
		</div>
	</div>

	<div class="card-img">
		<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-add-ons-img.png" alt="">
	</div>

</div>
