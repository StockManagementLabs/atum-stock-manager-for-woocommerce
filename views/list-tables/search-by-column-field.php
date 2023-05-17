<?php
/**
 * Search by column field used in some List Tables
 *
 * @var string $ajax
 */

?>
<span id="atum-search-orders" class="input-group input-group-sm">
	<span class="input-group-append">
		<button class="btn btn-outline-secondary dropdown-toggle atum-tooltip" id="search_column_btn"
			title="<?php esc_attr_e( 'Search in Column', ATUM_TEXT_DOMAIN ) ?>" data-value=""
			type="button" data-toggle="dropdown" data-bs-placement="left" aria-haspopup="true" aria-expanded="false">
			<?php esc_html_e( 'Search In', ATUM_TEXT_DOMAIN ) ?>
		</button>

		<div class="search-column-dropdown dropdown-menu" id="search_column_dropdown"
			data-product-title="<?php esc_attr_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?>"
			data-no-option="<?php esc_attr_e( 'Search In', ATUM_TEXT_DOMAIN ) ?>"
			data-no-option-title="<?php esc_attr_e( 'Search in Column', ATUM_TEXT_DOMAIN ) ?>">
		</div>
	</span>

	<input type="search" class="form-control atum-post-search atum-post-search-with-dropdown" data-value=""
		placeholder="<?php esc_attr_e( 'Search...', ATUM_TEXT_DOMAIN ) ?>" autocomplete="off">

	<span class="input-group-text">
		<img src="<?php echo esc_url( ATUM_URL . 'assets/images/atum-icon.svg' ) ?>" alt="<?php esc_attr_e( 'ATUM field', ATUM_TEXT_DOMAIN ) ?>">
	</span>

	<?php if ( 'no' === $ajax ) : ?>
		<input type="submit" class="button search-submit" value="<?php esc_attr_e( 'Search', ATUM_TEXT_DOMAIN ) ?>" disabled>
	<?php endif; ?>
</span>
