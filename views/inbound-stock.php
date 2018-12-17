<?php
/**
 * View for the Inbound Stock page
 *
 * @since 1.3.0
 *
 * @var \Atum\InboundStock\Lists\ListTable $list
 * @var string                             $ajax
 */

defined( 'ABSPATH' ) || die;

?>
<div class="wrap">
	<h1 class="wp-heading-inline extend-list-table">
		<?php echo esc_html( apply_filters( 'atum/inbound_stock/title', __( 'Inbound Stock', ATUM_TEXT_DOMAIN ) ) ) ?>
	</h1>
	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-action="atum_fetch_inbound_stock_list" data-screen="<?php echo esc_attr( $list->screen->id ) ?>">
		<div class="list-table-header">
			<?php $list->views(); ?>

			<p class="search-box inbound-stock-search">
				<input type="search" name="s" class="atum-post-search" value="" placeholder="<?php esc_attr_e( 'Search...', ATUM_TEXT_DOMAIN ) ?>" autocomplete="off">

				<?php if ( 'no' === $ajax ) : ?>
					<input type="submit" class="button search-submit" value="<?php esc_attr_e( 'Search', ATUM_TEXT_DOMAIN ) ?>">
				<?php endif; ?>
			</p>
		</div>

		<?php $list->display(); ?>

	</div>
</div>
