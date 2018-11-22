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
		<div class="stock-central-header">
			<div id="scroll-stock_central_nav" class="sc-nav-containe-box">
				<div class="overflow-opacity-effect-right" >

				</div>
				<div class="overflow-opacity-effect-left" >

				</div>
				<nav id="stock_central_nav" class="nav-with-scroll-effect">
					<?php $list->views(); ?>
				</nav>
			</div>

			<div class="search-box extend-list-table">
				<button type="button" class="reset-filters hidden tips" data-tip="<?php esc_attr_e( 'Reset Filters', ATUM_TEXT_DOMAIN ) ?>"><i class="dashicons dashicons-update"></i></button>
				<div class="input-group input-group-sm">
					<div class="input-group-append">
						<button class="btn btn-outline-secondary dropdown-toggle" id="search_column_btn" data-value="title" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<?php esc_html_e( 'Search in', ATUM_TEXT_DOMAIN ) ?>
						</button>

						<div class="search_column_dropdown dropdown-menu" id="search_column_dropdown"
								data-product-title="<?php esc_attr_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?>"
								data-no-option="<?php esc_attr_e( 'Search in Column', ATUM_TEXT_DOMAIN ) ?>"
						></div>
					</div>
					<input type="text" class="form-control atum-post-search atum-post-search-with-dropdown" data-value=""
							aria-label="Text input with dropdown button"
							data-no-option="<?php esc_attr_e( 'Search...', ATUM_TEXT_DOMAIN ) ?>"
							autocomplete="off">

					<?php if ( 'no' === $ajax ) : ?>
						<input type="submit" class="button search-submit" value="<?php esc_attr_e( 'Search', ATUM_TEXT_DOMAIN ) ?>" disabled>
					<?php endif; ?>
				</div>

				<?php
				$sticky_column = Helpers::get_option( 'sticky_columns' );
				$active        = FALSE;
				if ( 'yes' === $sticky_column ) {
					$active = TRUE;
				}
				?>

				<div class="sticky-columns-button-container">
					<button type="button" class="sticky-columns-button tips sticky-on <?php echo esc_attr( $active ? 'active' : '' ); ?>" data-option="yes" data-tip="Enable Sticky Columns">
						<i class="atmi-view-col-fixed-outline"></i>
					</button>

					<button type="button" class="sticky-columns-button tips sticky-off <?php echo esc_attr( ! $active ? 'active' : '' ); ?>" data-option="no" data-tip="Disable Sticky Columns">
						<i class="atmi-view-list-outline"></i>
					</button>
				</div>
			</div>
		</div>


		<?php $list->display(); ?>
		
	</div>
</div>
