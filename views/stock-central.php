<?php
/**
 * View for the Stock Central page
 *
 * @since 0.0.1
 */

defined( 'ABSPATH' ) or die;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e('Stock Central', ATUM_TEXT_DOMAIN) ?></h1>
	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-action="atum_fetch_stock_central_list">
		
		<?php $list->views(); ?>
		
		<input id="atum_selected_ids" name="atum_selected_ids" type="hidden" value="">
		<p class="search-box">
			<input type="search" name="s" class="atum-post-search" value="" placeholder="<?php _e('Search products...', ATUM_TEXT_DOMAIN) ?>">
			
			<?php if ( $ajax == 'no' ):?>
				<input type="submit" class="button search-submit" value="<?php _e('Search', ATUM_TEXT_DOMAIN) ?>">
			<?php endif;?>
		</p>
		
		<?php $list->display(); ?>
		
	</div>
</div>