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
<div class="atum-addons">

	<?php require 'header.php' ?>

	<?php if ( ! empty( $addons ) && is_array( $addons ) ) : ?>

		<div class="atum-addons-wrap" data-nonce="<?php echo esc_attr( wp_create_nonce( 'atum-addon-action' ) ) ?>">

			<div class="list-table-header">
				<div class="nav-container-box" style="visibility:hidden">
					<nav class="nav-with-scroll-effect dragscroll">
						<ul class="subsubsub extend-list-table">
							<li class="all" data-status="all"><span class="active"><?php esc_html_e( 'All', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="activated" data-status="valid"><span><?php esc_html_e( 'Activated', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="not-activated" data-status="not-activated"><span><?php esc_html_e( 'Not activated', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="expired" data-status="expired"><span><?php esc_html_e( 'Expired', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="not-installed" data-status="not-installed"><span><?php esc_html_e( 'Not installed', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="invalid" data-status="invalid"><span><?php esc_html_e( 'Invalid', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="trial" data-status="trial"><span><?php esc_html_e( 'Trial', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="coming-soon" data-status="coming-soon"><span><?php esc_html_e( 'Coming soon', ATUM_TEXT_DOMAIN ) ?></span></li>
						</ul>

						<div class="overflow-opacity-effect-right"></div>
						<div class="overflow-opacity-effect-left"></div>
					</nav>

					<span class="addons-search-wrapper">
						<input type="search" placeholder="<?php esc_html_e( 'Search...', ATUM_TEXT_DOMAIN ) ?>" value="" id="addons-search">
					</span>
				</div>
			</div>

			<div id="atum-addons-list">
				<?php foreach ( $addons as $addon ) :
					require 'add-on.php';
				endforeach; ?>

				<div class="alert alert-warning no-results" style="display: none">
					<i class="atum-icon atmi-warning"></i>
					<?php
					/* translators: the term span tag */
					printf( esc_html__( "No add-ons found with term '%s'", ATUM_TEXT_DOMAIN ), '<span class="no-results__term"></span>' ) ?>
				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
