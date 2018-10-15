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
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( apply_filters( 'atum/stock_central/title', __( 'Stock Central', ATUM_TEXT_DOMAIN ) ) ) ?>

		<?php if ( $is_uncontrolled_list ) : ?>
			<?php esc_html_e( '(Uncontrolled)', ATUM_TEXT_DOMAIN ) ?>
		<?php endif; ?>

		<a href="<?php echo esc_url( $sc_url ) ?>" class="toggle-managed page-title-action"><?php echo esc_html( $is_uncontrolled_list ? __( 'Show Controlled', ATUM_TEXT_DOMAIN ) : __( 'Show Uncontrolled', ATUM_TEXT_DOMAIN ) ) ?></a>
		<?php do_action( 'atum/stock_central_list/page_title_buttons' ) ?>
	</h1>

	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-action="atum_fetch_stock_central_list" data-screen="<?php echo esc_attr( $list->screen->id ) ?>">

		<?php $list->views(); ?>

		<div class="search-box">

			<div class="input-group input-group-sm">

				<input type="text" class="form-control atum-post-search atum-post-search-with-dropdown" data-value=""
					aria-label="Text input with dropdown button"
					data-no-option="<?php esc_attr_e( 'Search products...', ATUM_TEXT_DOMAIN ) ?>"
					placeholder="<?php esc_attr_e( 'Search products...', ATUM_TEXT_DOMAIN ) ?>" autocomplete="off">

				<div class="input-group-append">
					<button class="btn btn-outline-secondary dropdown-toggle" id="search_column_btn" data-value="title" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?php esc_html_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?>
					</button>

					<div class="search_column_dropdown dropdown-menu" id="search_column_dropdown"
						data-product-title="<?php esc_attr_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?>"
						data-no-option="<?php esc_attr_e( 'Search in Column', ATUM_TEXT_DOMAIN ) ?>"
					></div>
				</div>

				<?php if ( 'no' === $ajax ) : ?>
					<input type="submit" class="button search-submit" value="<?php esc_attr_e( 'Search', ATUM_TEXT_DOMAIN ) ?>" disabled>
				<?php endif; ?>

			</div>

		</div>

		<?php $list->display(); ?>
		
	</div>
</div>
