<?php
/**
 * View for the Stock Central page
 *
 * @since 0.0.1
 *
 * @var bool                               $is_uncontrolled_list
 * @var string                             $sc_url
 * @var \Atum\StockCentral\Lists\ListTable $list
 * @var string                             $ajax
 */

use Atum\Inc\Helpers;

defined( 'ABSPATH' ) || die;

?>
<div class="wrap">
	<h1 class="wp-heading-inline extend-list-table">
		<?php echo esc_html( apply_filters( 'atum/stock_central/title', __( 'Stock Central', ATUM_TEXT_DOMAIN ) ) ) ?>

		<?php if ( $is_uncontrolled_list ) : ?>
			<?php esc_html_e( '(Uncontrolled)', ATUM_TEXT_DOMAIN ) ?>
		<?php endif; ?>

		<a href="<?php echo esc_url( $sc_url ) ?>" class="toggle-managed page-title-action extend-list-table"><?php echo esc_html( $is_uncontrolled_list ? __( 'Show Controlled', ATUM_TEXT_DOMAIN ) : __( 'Show Uncontrolled', ATUM_TEXT_DOMAIN ) ) ?></a>
		<?php do_action( 'atum/stock_central_list/page_title_buttons' ) ?>
	</h1>

	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-action="atum_fetch_stock_central_list" data-screen="<?php echo esc_attr( $list->screen->id ) ?>">
		<div class="list-table-header">

			<div id="scroll-stock_central_nav" class="nav-container-box">
				<nav id="stock_central_nav" class="nav-with-scroll-effect dragscroll">
					<?php $list->views(); ?>

					<div class="overflow-opacity-effect-right"></div>
					<div class="overflow-opacity-effect-left"></div>
				</nav>
			</div>

			<div class="search-box extend-list-table">
				<button type="button" class="reset-filters hidden tips" data-tip="<?php esc_attr_e( 'Reset Filters', ATUM_TEXT_DOMAIN ) ?>"><i class="atum-icon atmi-undo"></i></button>

				<div class="input-group input-group-sm">
					<div class="input-group-append">
						<button class="btn btn-outline-secondary dropdown-toggle tips" id="search_column_btn"
								title="<?php esc_attr_e( 'Search in Column', ATUM_TEXT_DOMAIN ) ?>" data-value=""
								type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<?php esc_html_e( 'Search In', ATUM_TEXT_DOMAIN ) ?>
						</button>

						<div class="search_column_dropdown dropdown-menu" id="search_column_dropdown"
								data-product-title="<?php esc_attr_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?>"
								data-no-option="<?php esc_attr_e( 'Search In', ATUM_TEXT_DOMAIN ) ?>"
								data-no-option-title="<?php esc_attr_e( 'Search in Column', ATUM_TEXT_DOMAIN ) ?>">
						</div>
					</div>

					<input type="search" class="form-control atum-post-search atum-post-search-with-dropdown" data-value=""
							placeholder="<?php esc_attr_e( 'Search...', ATUM_TEXT_DOMAIN ) ?>" autocomplete="off">

					<?php if ( 'no' === $ajax ) : ?>
						<input type="submit" class="button search-submit" value="<?php esc_attr_e( 'Search', ATUM_TEXT_DOMAIN ) ?>" disabled>
					<?php endif; ?>
				</div>

				<div class="table-style-buttons" data-nonce="<?php echo wp_create_nonce( 'atum-list-table-style' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

					<?php $enabled_sticky_columns = Helpers::get_atum_user_meta( 'enabled_sc_sticky_columns' ); ?>
					<button type="button" class="sticky-columns-button tips <?php echo esc_attr( 'yes' === $enabled_sticky_columns ? 'active' : '' ); ?>" data-tip="<?php esc_attr_e( 'Toggle Sticky Columns', ATUM_TEXT_DOMAIN ) ?>">
						<i class="atmi-view-sidebar-left"></i>
					</button>

					<?php $enabled_sticky_header = Helpers::get_atum_user_meta( 'enabled_sc_sticky_header' ); ?>
					<button type="button" class="sticky-header-button tips <?php echo esc_attr( 'yes' === $enabled_sticky_header ? 'active' : '' ); ?>" data-tip="<?php esc_attr_e( 'Toggle Sticky Header', ATUM_TEXT_DOMAIN ) ?>">
						<i class="atmi-view-sticky-header"></i>
					</button>

				</div>
			</div>
		</div>

		<?php $list->display(); ?>
		
	</div>
</div>
