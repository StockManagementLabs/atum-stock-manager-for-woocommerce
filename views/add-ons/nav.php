<?php
/**
 * View for the Addons page nav
 *
 * @since 1.9.27
 *
 * @var array|bool $addons
 * @var array      $installed_addons
 */

defined( 'ABSPATH' ) || die;

?>
<div class="atum-addons__nav">
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

		<span class="atum-addons__search">
			<input type="search" placeholder="<?php esc_html_e( 'Search...', ATUM_TEXT_DOMAIN ) ?>" value="" id="addons-search">
		</span>

		<span class="atum-addons__nav-buttons">
			<button type="button" class="grid-view btn btn-sm btn-outline-primary"><i class="atum-icon atmi-view-grid"></i></button>
			<button type="button" class="list-view btn btn-sm btn-primary"><i class="atum-icon atmi-view-list"></i></button>
		</span>

	</div>
</div>
