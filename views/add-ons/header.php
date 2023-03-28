<?php
/**
 * View for the Addons page header
 *
 * @since 1.9.27
 *
 * @var array|bool $addons
 */

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Inc\Helpers;
?>
<section class="atum-addons__header">

	<div>

		<div class="atum-addons__header-logo">
			<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/add-ons/atum-logo-addons.svg" alt="ATUM Premium Add-ons">
			<h3><?php esc_html_e( 'Bring your e-commerce to the next level and get the complete control over ready to sell inventory in one beautifully designed stock management package.', ATUM_TEXT_DOMAIN ) ?></h3>
		</div>

		<div class="atum-addons__header-buttons">
			<a href="<?php echo esc_url( Addons::ADDONS_STORE_URL ) ?>addons/" target="_blank" type="button" class="btn btn-primary"><?php esc_html_e( 'Visit add-ons store', ATUM_TEXT_DOMAIN ) ?></a>
			<a href="#atum-addons-list" class="btn btn-outline-primary"><?php esc_html_e( 'View add-ons info', ATUM_TEXT_DOMAIN ) ?></a>
		</div>

	</div>

	<div class="atum-addons__header-notice">
		<?php echo wp_kses_post( Helpers::get_rating_text() ) ?>
	</div>
</section>
