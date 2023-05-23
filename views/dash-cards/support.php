<?php
/**
 * View for the support dash card
 *
 * @since 1.9.27
 */

defined( 'ABSPATH' ) || die;

?>
<div class="dash-card support">

	<div class="card-content">

		<div class="card-content__header">
			<h5 class="h5-tertiary"><?php esc_html_e( 'Support', ATUM_TEXT_DOMAIN ) ?></h5>
			<h2><?php esc_html_e( 'Free Support Forum', ATUM_TEXT_DOMAIN ) ?></h2>
		</div>

		<p><?php esc_html_e( "Get free support, suggest features, give your feedback and get involved on ATUM's community.", ATUM_TEXT_DOMAIN ) ?></p>

		<div class="card-content__buttons">
			<a href="https://forum.stockmanagementlabs.com/" class="btn btn-warning" target="_blank"><?php esc_html_e( 'Go to Forum', ATUM_TEXT_DOMAIN ) ?></a>
		</div>
	</div>

	<div class="card-img">
		<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-support-img.png" alt="">
	</div>

</div>
