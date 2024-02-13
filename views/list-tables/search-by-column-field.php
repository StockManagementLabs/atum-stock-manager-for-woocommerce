<?php
/**
 * Search by column field used in some List Tables
 *
 * @var string   $ajax
 * @var bool     $show_atum_icon
 * @var string[] $menu_items
 * @var string   $no_option
 * @var string   $no_option_title
 */

$dropdown_required = empty( $menu_items ) || count( $menu_items ) > 1;

?>
<span id="atum-search-by-column" class="input-group input-group-sm">
	<span class="input-group-append">

		<?php if ( $dropdown_required ) : ?>

			<button class="search-column-btn btn btn-outline-secondary dropdown-toggle atum-tooltip"
				title="<?php echo esc_attr( $no_option_title ?? __( 'Search in Column', ATUM_TEXT_DOMAIN ) ) ?>" data-value=""
				type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
			>
				<?php if ( empty( $_REQUEST['atum_search_column'] ) ) : ?>
					<?php echo esc_html( $no_option ?? __( 'Search In', ATUM_TEXT_DOMAIN ) ) ?>
				<?php else : ?>
					<?php echo esc_html( ucwords( $_REQUEST['atum_search_column'] ) ) ?>
				<?php endif; ?>
			</button>

			<div class="search-column-dropdown dropdown-menu" id="search_column_dropdown"
				data-product-title="<?php esc_attr_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?>"
				data-no-option="<?php echo esc_attr( $no_option ?? __( 'Search In', ATUM_TEXT_DOMAIN ) ) ?>"
				data-no-option-title="<?php echo esc_attr( $no_option_title ?? __( 'Search in Column', ATUM_TEXT_DOMAIN ) ) ?>"
			>
				<?php if ( ! empty( $menu_items ) ) : ?>

					<a href="#" class="dropdown-item active"><?php echo esc_html( $no_option ?? __( 'Search In', ATUM_TEXT_DOMAIN ) ) ?></a>

					<?php foreach ( $menu_items as $key => $menu_item ) : ?>
						<a href="#" class="dropdown-item" data-value="<?php echo esc_attr( $key ) ?>">
							<?php echo esc_html( $menu_item ) ?>
						</a>
					<?php endforeach; ?>

					<input type="hidden" name="atum_search_column" value="<?php echo esc_attr( $_REQUEST['atum_search_column'] ?? '' ) ?>">

				<?php endif; ?>
			</div>

		<?php else :
			$current_item = current( $menu_items ); ?>
			<span class="input-group-text atum-tooltip" title="<?php printf( esc_attr__( 'Search by %s', ATUM_TEXT_DOMAIN ), $current_item ) ?>">
				<?php echo esc_html( $current_item ) ?>
				<input type="hidden" name="atum_search_column" value="<?php echo esc_attr( key( $menu_items ) ) ?>">
			</span>
		<?php endif; ?>

	</span>

	<input type="search" class="form-control atum-post-search atum-post-search-with-dropdown" data-value=""
		placeholder="<?php esc_attr_e( 'Search...', ATUM_TEXT_DOMAIN ) ?>" autocomplete="off"
		name="atum_post_search"<?php echo ! empty( $_REQUEST['atum_post_search'] ) ? 'value="' . esc_attr( $_REQUEST['atum_post_search'] ) . '"' : '' ?>
	>

	<?php if ( ! empty( $show_atum_icon ) ) : ?>
		<span class="input-group-text atum-tooltip" title="<?php esc_attr_e( 'ATUM Search by Column', ATUM_TEXT_DOMAIN ) ?>">
			<img src="<?php echo esc_url( ATUM_URL . 'assets/images/atum-icon.svg' ) ?>" alt="<?php esc_attr_e( 'ATUM field', ATUM_TEXT_DOMAIN ) ?>">
		</span>
	<?php endif; ?>

	<?php if ( 'no' === $ajax ) : ?>
		<input type="submit" class="button search-submit" value="<?php esc_attr_e( 'Search', ATUM_TEXT_DOMAIN ) ?>" disabled>
	<?php endif; ?>
</span>
