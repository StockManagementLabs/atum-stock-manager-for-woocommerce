<?php
/**
 * View for the trials dash card
 *
 * @since 1.9.27
 */

defined( 'ABSPATH' ) || die;

?>
<div class="dash-card add-ons">

	<div class="card-content">

		<div class="card-content__header">
			<h5 class="h5-secondary"><?php esc_html_e( 'Premium Add-ons', ATUM_TEXT_DOMAIN ) ?></h5>
			<h2><?php esc_html_e( 'Free Trial Period', ATUM_TEXT_DOMAIN ) ?></h2>
		</div>

		<p><?php esc_html_e( "Now it's possible to try all our premium add-ons for free installing the trial version. Just buy a free license for the add-on you want to try, install it from here and try it with all the features during 14 days.", ATUM_TEXT_DOMAIN ) ?></p>

		<div class="card-content__buttons">
			<a href="https://stockmanagementlabs.com/addons/" class="btn btn-tertiary" target="_blank"><?php esc_html_e( 'Get Started', ATUM_TEXT_DOMAIN ) ?></a>
		</div>
	</div>

	<div class="card-img">
		<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-add-ons-img.png" alt="">
	</div>

</div>
