<?php
/**
 * View for the Addons page list
 *
 * @since 1.9.27
 *
 * @var array|bool $addons
 * @var array      $installed_addons
 */

defined( 'ABSPATH' ) || die;

?>
<div class="atum-addons" data-nonce="<?php echo esc_attr( wp_create_nonce( ATUM_PREFIX . 'manage_license' ) ) ?>">

	<?php require 'header.php' ?>

	<?php if ( ! empty( $addons ) && is_array( $addons ) ) : ?>

		<div class="atum-addons-wrap" data-nonce="<?php echo esc_attr( wp_create_nonce( 'atum-addon-action' ) ) ?>">

			<div class="list-table-header">
				<div class="nav-container-box">
					<nav class="nav-with-scroll-effect dragscroll">
						<ul class="subsubsub extend-list-table">
							<li class="all" data-status="all"><span class="active"><?php esc_html_e( 'All', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="activated" data-status="valid"><span><?php esc_html_e( 'Activated', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="not-activated" data-status="not-activated"><span><?php esc_html_e( 'Not activated', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="expired" data-status="expired"><span><?php esc_html_e( 'Expired', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="not-installed" data-status="not-installed"><span><?php esc_html_e( 'Not installed', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="invalid" data-status="invalid"><span><?php esc_html_e( 'Invalid', ATUM_TEXT_DOMAIN ) ?></span></li>
						</ul>

						<div class="overflow-opacity-effect-right"></div>
						<div class="overflow-opacity-effect-left"></div>
					</nav>
				</div>
			</div>

			<div id="atum-addons-list">
				<?php foreach ( $addons as $addon ) :
					require 'add-on.php';
				endforeach; ?>
			</div>
		</div>

	<?php endif; ?>
</div>
