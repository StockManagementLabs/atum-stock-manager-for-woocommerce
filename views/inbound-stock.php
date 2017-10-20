<?php
/**
 * View for the Inbound Stock page
 *
 * @since 1.3.0
 */

defined( 'ABSPATH' ) or die;
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo apply_filters( 'atum/inbound_stock/title', __('Inbound Stock', ATUM_TEXT_DOMAIN) ) ?>
	</h1>
	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-action="atum_fetch_inbound_stock_list">
		
		<?php $list->views(); ?>

		<p class="search-box">
			<input type="search" name="s" class="atum-post-search" value="" placeholder="<?php _e('Search products...', ATUM_TEXT_DOMAIN) ?>">
			
			<?php if ( $ajax == 'no' ):?>
				<input type="submit" class="button search-submit" value="<?php _e('Search', ATUM_TEXT_DOMAIN) ?>">
			<?php endif;?>
		</p>
		
		<?php $list->display(); ?>
		
	</div>
</div>