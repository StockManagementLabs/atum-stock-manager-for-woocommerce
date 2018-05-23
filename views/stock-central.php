<?php
/**
 * View for the Stock Central page
 *
 * @since 0.0.1
 */

defined( 'ABSPATH' ) or die;
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo apply_filters( 'atum/stock_central/title', __('Stock Central', ATUM_TEXT_DOMAIN) ) ?>

		<?php if ($is_uncontrolled_list): ?>
			<?php _e('(Uncontrolled)', ATUM_TEXT_DOMAIN) ?>
		<?php endif; ?>

		<a href="<?php echo $sc_url ?>" class="toggle-managed page-title-action"><?php echo $is_uncontrolled_list ? __('Show Controlled', ATUM_TEXT_DOMAIN) : __('Show Uncontrolled', ATUM_TEXT_DOMAIN) ?></a>
		<?php do_action('atum/list_table/page_title_buttons') ?>
	</h1>

	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-action="atum_fetch_stock_central_list" data-screen="<?php echo $list->screen->id ?>">
		
		<?php $list->views(); ?>
        
        <div class="search-box">

            <div class="input-group">
                <input type="text"
                       class="form-control atum-post-search atum-post-search-with-dropdown" data-value=""
                       aria-label="Text input with dropdown button"
                       placeholder="<?php _e('Search products...', ATUM_TEXT_DOMAIN) ?>" autocomplete="off"
                >
                <div class="input-group-append">
                    <button class="button btn-outline-secondary dropdown-toggle" id="search_column_btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php _e('Select a Column', ATUM_TEXT_DOMAIN) ?>
                    </button>
                    <div class="search_column_dropdown dropdown-menu" id="search_column_dropdown">
                        <a class="dropdown-item" data-value="aaaa" href="#">Action</a>
                    </div>
                </div>

	            <?php if ( $ajax == 'no' ):?>
                    <input type="submit" class="button search-submit" value="<?php _e('Search', ATUM_TEXT_DOMAIN) ?>">
	            <?php endif;?>

            </div>

		</div>

        <input type="text"
               class="form-control atum-post-search atum-post-search-with-dropdown" data-value=""
               aria-label="Text input with dropdown button"
               placeholder="tessst" autocomplete="off"
        >
		
		<?php $list->display(); ?>
		
	</div>
</div>