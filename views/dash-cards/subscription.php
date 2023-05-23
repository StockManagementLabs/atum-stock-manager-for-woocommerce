<?php
/**
 * View for the subscription dash card
 *
 * @since 1.9.27
 */

defined( 'ABSPATH' ) || die;

?>
<div class="dash-card subscription">

	<div class="card-content">

		<div class="card-content__header">
			<h5 class="h5-tertiary"><?php esc_html_e( 'Newsletter', ATUM_TEXT_DOMAIN ) ?></h5>
			<h2><?php esc_html_e( 'Earn Regular Rewards', ATUM_TEXT_DOMAIN ) ?></h2>
		</div>

		<p><?php esc_html_e( 'Thank you very much for choosing ATUM as your inventory manager. Please, subscribe to receive news and updates and earn regular rewards.', ATUM_TEXT_DOMAIN ) ?></p>
	</div>

	<div class="card-img">
		<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-subscription-img.png" alt="">
	</div>

	<form action="https://stockmanagementlabs.us12.list-manage.com/subscribe/post?u=bc146f9acefd460717d243671&id=b0263fe4a6" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
		<div class="input-group">
			<input type="email" name="EMAIL" id="mce-EMAIL"  placeholder="<?php esc_attr_e( 'Enter your email address', ATUM_TEXT_DOMAIN ) ?>" required>
			<button type="submit" class="btn btn-secondary" name="subscribe" id="mc-embedded-subscribe"><?php esc_html_e( 'Subscribe', ATUM_TEXT_DOMAIN ) ?></button>
		</div>
	</form>

</div>
