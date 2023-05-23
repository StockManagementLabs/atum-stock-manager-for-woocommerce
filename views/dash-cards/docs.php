<?php
/**
 * View for the docs dash card
 *
 * @since 1.9.27
 */

defined( 'ABSPATH' ) || die;

?>
<div class="dash-card docs">

	<div class="card-content">
		<div class="card-content__header">
			<h5 class="h5-primary"><?php esc_html_e( 'Documentation', ATUM_TEXT_DOMAIN ) ?></h5>
			<h2><?php esc_html_e( 'Complete Tutorials', ATUM_TEXT_DOMAIN ) ?></h2>
		</div>

		<p><?php esc_html_e( "Our team is working daily to document ATUM's fast-growing content. Browse our detailed tutorials, ask questions or share feature requests with our team.", ATUM_TEXT_DOMAIN ) ?></p>

		<div class="card-content__buttons">
			<a href="https://stockmanagementlabs.crunch.help/" class="btn btn-primary" target="_blank"><?php esc_html_e( 'Read the Docs', ATUM_TEXT_DOMAIN ) ?></a>
		</div>
	</div>

	<div class="card-img">
		<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-docs-img.png" alt="">
	</div>

</div>
