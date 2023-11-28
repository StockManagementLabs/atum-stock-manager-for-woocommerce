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

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

?>
<div class="wrap">
	<h1 class="wp-heading-inline extend-list-table">
		<?php echo esc_html( apply_filters( 'atum/stock_central/title', __( 'Stock Central', ATUM_TEXT_DOMAIN ) ) ) ?>

		<?php if ( $is_uncontrolled_list ) : ?>
			<?php esc_html_e( '(Uncontrolled)', ATUM_TEXT_DOMAIN ) ?>
		<?php endif; ?>

		<a id="atum-stock-central-lists-button" href="<?php echo esc_url( $sc_url ) ?>" class="toggle-managed page-title-action extend-list-table"><?php echo esc_html( $is_uncontrolled_list ? __( 'Show Controlled', ATUM_TEXT_DOMAIN ) : __( 'Show Uncontrolled', ATUM_TEXT_DOMAIN ) ) ?></a>

		<?php do_action( 'atum/stock_central_list/page_title_buttons' ) ?>
	</h1>

	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-list="<?php echo esc_attr( $list->get_id() ) ?>" data-action="atum_fetch_stock_central_list"
	     data-screen="<?php echo esc_attr( $list->screen->id ) ?>"
	>
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

				<?php
				$show_atum_icon = FALSE;
				Helpers::load_view( 'list-tables/search-by-column-field', compact( 'ajax', 'show_atum_icon' ) );
				?>

				<div class="table-style-buttons" data-nonce="<?php echo wp_create_nonce( 'atum-list-table-style' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

					<?php $enabled_sticky_columns = Helpers::get_atum_user_meta( 'enabled_sc_sticky_columns' ); ?>
					<button type="button" class="sticky-columns-button atum-tooltip <?php echo esc_attr( 'yes' === $enabled_sticky_columns ? 'active' : '' ); ?>"
						title="<?php esc_attr_e( 'Toggle Sticky Columns', ATUM_TEXT_DOMAIN ) ?>" data-bs-placement="bottom"
						data-feature="sticky-columns" data-save-meta="1"
					>
						<i class="atum-icon atmi-view-sidebar-left"></i>
					</button>

					<?php $enabled_sticky_header = Helpers::get_atum_user_meta( 'enabled_sc_sticky_header' ); ?>
					<button type="button" class="sticky-header-button atum-tooltip <?php echo esc_attr( 'yes' === $enabled_sticky_header ? 'active' : '' ); ?>"
						title="<?php esc_attr_e( 'Toggle Sticky Header', ATUM_TEXT_DOMAIN ) ?>" data-bs-placement="bottom"
						data-feature="sticky-header" data-save-meta="1"
					>
						<i class="atum-icon atmi-view-sticky-header"></i>
					</button>

					<?php $expandable_rows = Helpers::get_option( 'expandable_rows', 'no' ); ?>
					<button type="button" class="expand-button atum-tooltip <?php echo esc_attr( 'yes' === $expandable_rows ? 'active' : '' ); ?>"
						title="<?php esc_attr_e( 'Expand/Collapse all the rows', ATUM_TEXT_DOMAIN ) ?>" data-bs-placement="bottom"
						data-feature="expand"
					>
						<i class="atum-icon atmi-wc-expand"></i>
					</button>

				</div>
			</div>
		</div>

		<?php $list->display(); ?>
		
	</div>
</div>
